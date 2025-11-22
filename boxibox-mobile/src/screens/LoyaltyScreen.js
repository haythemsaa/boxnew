import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import loyaltyService from '../services/loyaltyService';
import Card from '../components/Card';
import Loading from '../components/Loading';
import { COLORS } from '../constants/colors';

const LoyaltyScreen = () => {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loyalty, setLoyalty] = useState(null);
  const [history, setHistory] = useState([]);
  const [programInfo, setProgramInfo] = useState(null);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const [balanceData, historyData, infoData] = await Promise.all([
        loyaltyService.getBalance(),
        loyaltyService.getHistory(),
        loyaltyService.getProgramInfo(),
      ]);

      setLoyalty(balanceData.loyalty);
      setHistory(historyData.transactions || []);
      setProgramInfo(infoData.program);
    } catch (error) {
      console.error('Error loading loyalty data:', error);
      Alert.alert('Erreur', 'Impossible de charger les données de fidélité');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadData();
  };

  if (loading) {
    return <Loading />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView
        style={styles.scrollView}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        {loyalty && (
          <>
            <Card style={styles.pointsCard}>
              <Text style={styles.pointsLabel}>Mes points</Text>
              <Text style={styles.pointsValue}>{loyalty.points}</Text>
              <Text style={styles.tier}>Niveau : {loyalty.tier_label}</Text>
              {loyalty.tier_discount > 0 && (
                <Text style={styles.discount}>Réduction : {loyalty.tier_discount}%</Text>
              )}
              {loyalty.points_to_next_tier > 0 && (
                <Text style={styles.nextTier}>
                  {loyalty.points_to_next_tier} points pour le niveau suivant
                </Text>
              )}
            </Card>

            <Card>
              <Text style={styles.sectionTitle}>Statistiques</Text>
              <View style={styles.stat}>
                <Text style={styles.statLabel}>Points gagnés</Text>
                <Text style={styles.statValue}>{loyalty.points_earned}</Text>
              </View>
              <View style={styles.stat}>
                <Text style={styles.statLabel}>Points dépensés</Text>
                <Text style={styles.statValue}>{loyalty.points_spent}</Text>
              </View>
            </Card>
          </>
        )}

        {history.length > 0 && (
          <Card>
            <Text style={styles.sectionTitle}>Historique récent</Text>
            {history.slice(0, 10).map((transaction) => (
              <View key={transaction.id} style={styles.transaction}>
                <View style={styles.transactionLeft}>
                  <Text style={styles.transactionDescription}>
                    {transaction.description}
                  </Text>
                  <Text style={styles.transactionDate}>
                    {new Date(transaction.created_at).toLocaleDateString('fr-FR')}
                  </Text>
                </View>
                <Text
                  style={[
                    styles.transactionPoints,
                    transaction.type === 'earned' ? styles.earned : styles.spent,
                  ]}
                >
                  {transaction.type === 'earned' ? '+' : '-'}
                  {transaction.points}
                </Text>
              </View>
            ))}
          </Card>
        )}

        {programInfo && (
          <Card>
            <Text style={styles.sectionTitle}>Programme de fidélité</Text>
            <Text style={styles.programName}>{programInfo.name}</Text>

            {programInfo.tiers && (
              <>
                <Text style={styles.subsectionTitle}>Niveaux</Text>
                {programInfo.tiers.map((tier, index) => (
                  <View key={index} style={styles.tierInfo}>
                    <Text style={styles.tierName}>{tier.name}</Text>
                    <Text style={styles.tierRange}>
                      {tier.min_points} - {tier.max_points} points
                    </Text>
                    {tier.discount > 0 && (
                      <Text style={styles.tierBenefit}>-{tier.discount}% de réduction</Text>
                    )}
                  </View>
                ))}
              </>
            )}
          </Card>
        )}
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  scrollView: {
    flex: 1,
    padding: 16,
  },
  pointsCard: {
    alignItems: 'center',
    paddingVertical: 24,
    backgroundColor: COLORS.primary,
  },
  pointsLabel: {
    fontSize: 16,
    color: COLORS.white,
    opacity: 0.9,
  },
  pointsValue: {
    fontSize: 48,
    fontWeight: 'bold',
    color: COLORS.white,
    marginVertical: 8,
  },
  tier: {
    fontSize: 16,
    color: COLORS.white,
    fontWeight: '600',
  },
  discount: {
    fontSize: 14,
    color: COLORS.white,
    marginTop: 4,
  },
  nextTier: {
    fontSize: 12,
    color: COLORS.white,
    opacity: 0.8,
    marginTop: 8,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 12,
  },
  subsectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.text.primary,
    marginTop: 16,
    marginBottom: 8,
  },
  stat: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
  },
  statLabel: {
    fontSize: 14,
    color: COLORS.text.secondary,
  },
  statValue: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.text.primary,
  },
  transaction: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.divider,
  },
  transactionLeft: {
    flex: 1,
  },
  transactionDescription: {
    fontSize: 14,
    color: COLORS.text.primary,
    marginBottom: 4,
  },
  transactionDate: {
    fontSize: 12,
    color: COLORS.text.secondary,
  },
  transactionPoints: {
    fontSize: 16,
    fontWeight: 'bold',
  },
  earned: {
    color: COLORS.success,
  },
  spent: {
    color: COLORS.error,
  },
  programName: {
    fontSize: 16,
    color: COLORS.text.primary,
    marginBottom: 16,
  },
  tierInfo: {
    paddingVertical: 8,
    borderLeftWidth: 3,
    borderLeftColor: COLORS.primary,
    paddingLeft: 12,
    marginBottom: 8,
  },
  tierName: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.text.primary,
  },
  tierRange: {
    fontSize: 12,
    color: COLORS.text.secondary,
    marginTop: 2,
  },
  tierBenefit: {
    fontSize: 12,
    color: COLORS.primary,
    marginTop: 2,
  },
});

export default LoyaltyScreen;
