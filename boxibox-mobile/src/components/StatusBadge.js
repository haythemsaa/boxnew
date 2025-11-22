import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { COLORS } from '../constants/colors';

const StatusBadge = ({ status, label }) => {
  const getStatusColor = (status) => {
    switch (status) {
      case 'active':
      case 'paid':
      case 'succeeded':
        return COLORS.success;
      case 'pending':
      case 'sent':
        return COLORS.warning;
      case 'overdue':
      case 'unpaid':
      case 'failed':
      case 'terminated':
        return COLORS.error;
      case 'suspended':
        return COLORS.gray[500];
      default:
        return COLORS.info;
    }
  };

  const backgroundColor = getStatusColor(status);

  return (
    <View style={[styles.badge, { backgroundColor: backgroundColor + '20' }]}>
      <Text style={[styles.text, { color: backgroundColor }]}>{label}</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  badge: {
    paddingVertical: 4,
    paddingHorizontal: 12,
    borderRadius: 12,
    alignSelf: 'flex-start',
  },
  text: {
    fontSize: 12,
    fontWeight: '600',
  },
});

export default StatusBadge;
